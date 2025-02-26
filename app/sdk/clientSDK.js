import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.14.0/firebase-app.js';
import { getMessaging, getToken, onMessage } from 'https://www.gstatic.com/firebasejs/10.14.0/firebase-messaging.js';

class PushBaseLogger {
    log(message, level = 'info') {
        const timestamp = new Date().toISOString();
        const logLevels = {
            'info': console.log,
            'warn': console.warn,
            'error': console.error
        };

        const logMethod = logLevels[level] || console.log;
        logMethod(`[${timestamp}] [PushBase clientSDK] [${level.toUpperCase()}] ${message}`);
    }
}

class PushBase {
    constructor(options = {}) {
        this.options = {
            registrationDelay: options.registrationDelay || 0,
            customSegments: options.customSegments || {},
            enableLogging: options.enableLogging || false
        };
        this.logger = new PushBaseLogger();
    }

    isSiteAllowed() {
        const allowed = '{{CLIENT_URL}}' === '*' || window.location.href.includes('{{CLIENT_URL}}');
        this.logger.log(`Site allowed check: ${allowed}`, 'info');
        return allowed;
    }

    isBrowserSupported() {
        const browser = this.detectBrowserDetails();
        const os = this.detectOsDetails();
        
        const supportedBrowsers = {
            'Google Chrome': { minVersion: 42 },
            'Mozilla Firefox': { minVersion: 45 },
            'Safari': { minVersion: 11 },
            'Microsoft Edge': { minVersion: 79 },
            'Opera': { minVersion: 36 }
        };

        const browserSupport = supportedBrowsers[browser.name];
        const isVersionSupported = browserSupport
            ? parseFloat(browser.version) >= browserSupport.minVersion
            : true;

        const isOsSupported = !(
            os.name === 'iOS' && parseFloat(os.version) < 11 ||
            os.name === 'Android' && parseFloat(os.version) < 7
        );

        const supported = isVersionSupported && isOsSupported;
        
        this.logger.log(`Browser support check: ${supported} (OS: ${os.name} ${os.version}, Browser: ${browser.name} ${browser.version})`, 'info');
        return supported;
    }

    async detectDeviceType() {
        const userAgent = navigator.userAgent;
        const deviceType = /Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(userAgent)
            ? 'mobile'
            : (/tablet|ipad|playbook|silk|(android(?!.*mobile))/i.test(userAgent)
                ? 'tablet'
                : 'desktop');
        
        this.logger.log(`Detected device type: ${deviceType}`, 'info');
        return deviceType;
    }

    detectBrowserDetails() {
        const userAgent = navigator.userAgent;
        const navigatorInfo = navigator.userAgentData || {};

        const KnownBrowsers = {
            chrome: "Google Chrome",
            brave: "Brave",
            crios: "Google Chrome",
            edge: "Microsoft Edge",
            edg: "Microsoft Edge",
            edgios: "Microsoft Edge",
            fennec: "Mozilla Firefox",
            jsdom: "JsDOM",
            mozilla: "Mozilla Firefox",
            fxios: "Mozilla Firefox",
            msie: "Microsoft Internet Explorer",
            opera: "Opera",
            opios: "Opera",
            opr: "Opera",
            opt: "Opera",
            rv: "Microsoft Internet Explorer",
            safari: "Safari",
            samsungbrowser: "Samsung Browser",
            electron: "Electron"
        };

        let browser = "Unknown";
        let version = "Unknown";

        if (navigatorInfo.brands) {
            const matchedBrand = navigatorInfo.brands.find(brand =>
                Object.keys(KnownBrowsers).includes(brand.brand.toLowerCase())
            );

            if (matchedBrand) {
                browser = KnownBrowsers[matchedBrand.brand.toLowerCase()];
                version = matchedBrand.version;
            }
        }

        if (browser === "Unknown") {
            const browserTests = [
                { name: 'Chrome', regex: /Chrome\/(\d+\.\d+)/, exclude: /Chromium|Edg/ },
                { name: 'Firefox', regex: /Firefox\/(\d+\.\d+)/ },
                { name: 'Safari', regex: /Version\/(\d+\.\d+)/, exclude: /Chrome/ },
                { name: 'Edge', regex: /Edg\/(\d+\.\d+)/ },
                { name: 'Opera', regex: /Opera\/(\d+\.\d+)/ }
            ];

            for (const test of browserTests) {
                if (test.exclude && test.exclude.test(userAgent)) continue;
                const match = userAgent.match(test.regex);
                if (match) {
                    browser = KnownBrowsers[match[0].split('/')[0].toLowerCase()] || test.name;
                    version = match[1];
                    break;
                }
            }
        }

        if (browser === "Google Chrome" && typeof window.navigator.brave?.isBrave === "function") {
            browser = "Brave";
        }

        return {
            name: browser,
            version: version
        };
    }

    async detectOsDetails() {
        const userAgent = navigator.userAgent;
        const navigatorInfo = navigator.userAgentData || {};
        
        const platforms = [
            {
                name: 'Windows',
                regex: /Windows NT (\d+\.\d+)/,
                defaultVersion: 'Unknown',
                platformCheck: ['Windows', 'Win32', 'Win64']
            },
            {
                name: 'MacOS',
                regex: /Mac OS X (\d+[._]\d+)/,
                transform: v => v.replace('_', '.'),
                platformCheck: ['MacIntel', 'Macintosh', 'Mac']
            },
            {
                name: 'Linux',
                regex: /Linux/,
                defaultVersion: '',
                platformCheck: ['Linux', 'X11']
            },
            {
                name: 'Android',
                regex: /Android (\d+\.\d+)/,
                defaultVersion: 'Unknown',
                platformCheck: ['Android']
            },
            {
                name: 'iOS',
                regex: /OS (\d+_\d+)/,
                transform: v => v.replace('_', '.'),
                platformCheck: ['iPhone', 'iPad', 'iPod']
            }
        ];

        if (navigatorInfo.platform) {
            const matchedPlatform = platforms.find(p =>
                p.platformCheck.some(check =>
                    navigatorInfo.platform.toLowerCase().includes(check.toLowerCase())
                )
            );

            if (matchedPlatform) {
                return {
                    name: matchedPlatform.name,
                    version: navigatorInfo.platformVersion
                        ? navigatorInfo.platformVersion.join('.')
                        : 'Unknown',
                    deviceType: this.detectDeviceType()
                };
            }
        }

        for (const platform of platforms) {
            const match = userAgent.match(platform.regex);
            if (match) {
                return {
                    name: platform.name,
                    version: platform.transform
                        ? platform.transform(match[1])
                        : match[1] || platform.defaultVersion,
                    deviceType: this.detectDeviceType()
                };
            }
        }

        const deviceInfo = {
            name: "Unknown",
            version: "Unknown",
            deviceType: await this.detectDeviceType(),
            architecture: navigator.userAgent.includes('WOW64') || navigator.userAgent.includes('Win64')
                ? '64-bit'
                : '32-bit'
        };

        return deviceInfo;
    }

    async collectAnalyticsData() {
        const browser = this.detectBrowserDetails();
        const os = await this.detectOsDetails();
        const analyticsData = {
            browser: {
                name: browser.name,
                version: browser.version
            },
            os: {
                name: os.name,
                version: os.version
            },
            device: await this.detectDeviceType(),
            screen: {
                width: window.screen.width,
                height: window.screen.height
            },
            language: navigator.language,
            tag: this.options.customSegments.tag,
            category: this.options.customSegments.category
        };

        this.logger.log(`Collected analytics data: ${JSON.stringify(analyticsData)}`, 'info');
        return analyticsData;
    }

    async checkPermission() {
        const hasPermission = Notification.permission === "granted";
        this.logger.log(`Notification permission check: ${hasPermission}`, 'info');
        
        if (Notification.permission === "granted") return true;
        if (Notification.permission !== "denied") {
            const permission = await Notification.requestPermission();
            return permission === "granted";
        }
        return false;
    }

    async registerServiceWorker(scriptPath = '/pushBaseSW.js') {
        if (!('serviceWorker' in navigator)) {
            this.logger.log('Service Workers are not supported in this browser', 'error');
            throw new Error('Service Workers not supported');
        }

        try {
            this.logger.log(`Attempting to register service worker with path: ${scriptPath}`, 'info');

            const fullScriptPath = new URL(scriptPath, window.location.origin).href;
            this.logger.log(`Resolved full service worker script path: ${fullScriptPath}`, 'info');

            const registration = await navigator.serviceWorker.register(fullScriptPath);

            if (!registration) {
                this.logger.log('Service Worker registration returned undefined', 'error');
                throw new Error('Service Worker registration failed');
            }

            this.logger.log(`Service Worker registered successfully with scope: ${registration.scope}`, 'info');

            if (!registration.pushManager) {
                this.logger.log('pushManager is not available on the service worker registration', 'error');
                throw new Error('pushManager not available');
            }

            return registration;
        } catch (error) {
            this.logger.log(`Service Worker registration failed: ${error.message}`, 'error');

            if (error instanceof TypeError) {
                this.logger.log('This might be due to an invalid script path or network issues', 'error');
            }
            
            throw error;
        }
    }

    async subscribe(registration = null) {
        try {
            if (!registration) {
                registration = await this.registerServiceWorker('pushBaseSW.js');
            }

            await new Promise((resolve, reject) => {
                const checkServiceWorkerState = () => {
                    if (registration.active) {
                        resolve();
                    } else {
                        this.logger.log('Waiting for service worker to become active', 'info');
                        setTimeout(checkServiceWorkerState, 100);
                    }
                };
                checkServiceWorkerState();
            });

            if (!registration.pushManager) {
                this.logger.log('pushManager is not available on the service worker registration', 'error');
                throw new Error('pushManager not available');
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array('{{VAPID_PUBLIC_KEY}}')
            });
            this.logger.log(`User subscribed: ${JSON.stringify(subscription)}`, 'info');

            await this.sendSubscriptionToServer(subscription);

            return subscription;
        } catch (error) {
            this.logger.log(`Failed to subscribe the user: ${error.message}`, 'error');
            throw error;
        }
    }

    async sendSubscriptionToServer(subscription) {
        try {
            const keys = subscription.toJSON().keys;
            const analyticsData = await this.collectAnalyticsData();

            const subscriptionData = {
                endpoint: subscription.endpoint,
                p256dh: keys.p256dh,
                authKey: keys.auth,
                action: 'subscription',
                subscription_action: 'subscribe',
                appkey: this.options.appKey,
                analyticsData: analyticsData
            };

            this.logger.log(`Sending complete subscription data: ${JSON.stringify(subscriptionData)}`, 'info');

            const response = await fetch(`{{APP_URL}}/api/subscriber`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(subscriptionData)
            });

            const data = await response.json();
            
            if (!response.ok) {
                this.logger.log(`Failed to send subscription data: ${data.message}`, 'error');
                
                if (response.status === 404) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    for (let registration of registrations) {
                        await registration.unregister();
                    }
                }
                
                throw new Error(data.message || 'Subscription failed');
            }

            this.logger.log(`Subscription data sent to server: ${JSON.stringify(data)}`, 'info');
            return data;
        } catch (error) {
            this.logger.log(`Error sending subscription data: ${error.message}`, 'error');
            throw error;
        }
    }

    async clearSubscriptionData() {
        try {
            localStorage.removeItem('pushbase_subscription');
            localStorage.removeItem('pushbase_user_token');
            localStorage.removeItem('pushbase_recent_notifications');

            const databases = await indexedDB.databases();
            for (let db of databases) {
                if (db.name.startsWith('pushbase_')) {
                    indexedDB.deleteDatabase(db.name);
                }
            }

            document.cookie.split(";").forEach((c) => {
                document.cookie = c
                    .replace(/^ +/, "")
                    .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
            });

            this.logger.log('Subscription data cleared successfully', 'info');
        } catch (error) {
            this.logger.log(`Error clearing subscription data: ${error.message}`, 'error');
            throw error;
        }
    }

    async unsubscribe() {
        try {
            const registrations = await navigator.serviceWorker.getRegistrations();
            
            let subscription = null;
            for (let registration of registrations) {
                const currentSubscription = await registration.pushManager.getSubscription();
                if (currentSubscription) {
                    subscription = currentSubscription;
                    break;
                }
            }

            if (!subscription) {
                throw new Error('No active push subscription found');
            }

            const response = await fetch(`{{APP_URL}}/api/subscriber/unsubscribe`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: subscription.endpoint
                })
            });

            const responseData = await response.json();

            if (!response.ok) {
                for (let registration of registrations) {
                    await registration.unregister();
                }
                
                if (response.status === 404) {
                    throw new Error('Subscriber not found');
                } else if (response.status === 400) {
                    throw new Error('Invalid unsubscribe request');
                } else {
                    throw new Error(responseData.message || 'Failed to unsubscribe');
                }
            }

            await this.clearSubscriptionData();

            for (let registration of registrations) {
                await registration.unregister();
            }

            return responseData;
        } catch (error) {
            const registrations = await navigator.serviceWorker.getRegistrations();
            for (let registration of registrations) {
                await registration.unregister();
            }
            
            await this.clearSubscriptionData();
            
            this.logger.log(`Unsubscribe error: ${error.message}`, 'error');
            throw error;
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
    }
}

class PushNotificationManager extends PushBase {
    constructor(options) {
        super(options);
        this.init();
    }

    async init() {
        if (!this.isSiteAllowed()) {
            this.logger.log("Application allowed only for specific sites.", 'warn');
            return false;
        }

        if (!this.isBrowserSupported()) {
            this.logger.log("Application cannot work on this browser or OS.", 'warn');
            return false;
        }

        try {
            const hasPermission = await this.checkPermission();
            if (hasPermission) {
                const registration = await this.registerServiceWorker();
                const subscription = await this.subscribe(registration);
                await this.sendSubscriptionToServer(subscription);
                return true;
            }
            return false;
        } catch (error) {
            this.logger.log(`Initialization failed: ${error.message}`, 'error');
            return false;
        }
    }
}

class PushBaseClient extends PushBase {
    constructor(config = {}) {
        super({
            registrationDelay: config.registrationDelay || 0,
            customSegments: config.customSegments || {}
        });
        this.firebaseConfig = config.firebaseConfig || {
            apiKey: "{{FIREBASE_APIKEY}}",
            authDomain: "{{FIREBASE_AUTHDOMAIN}}",
            projectId: "{{FIREBASE_PROJECTID}}",
            storageBucket: "{{FIREBASE_STORAGEBUCKET}}",
            messagingSenderId: "{{FIREBASE_MESSAGINGSENDERID}}",
            appId: "{{FIREBASE_APPID}}",
            measurementId: "{{FIREBASE_MEASUREMENTID}}"
        };

        this.NOTIFICATION_STORAGE_KEY = 'pushbase-'+this.firebaseConfig.appId;

        this.registrationMode = config.registrationMode || 'manual';

        this.app = initializeApp(this.firebaseConfig);
        this.messaging = getMessaging(this.app);

        if (this.registrationMode === 'auto') {
            this.subscribe();
        }
    }

    async checkUserStatus(endpoint) {
        try {
            const response = await fetch(`{{APP_URL}}/api/subscriber/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: endpoint,
                    appkey: this.firebaseConfig.appId
                })
            });

            const data = await response.json();
            this.logger.log(`User status full response: ${JSON.stringify(data)}`, 'info');

            if (!response.ok) {
                this.logger.log(`Failed to check user status: ${data.message || 'Unknown error'}`, 'error');
                return false;
            }

            const isActive = data.status === 'active';
            this.logger.log(`User is active: ${isActive}`, 'info');

            if (!isActive) {
                this.logger.log(`Inactive user details: ${JSON.stringify(data)}`, 'warn');
            }

            return isActive;
        } catch (error) {
            this.logger.log(`Error checking user status: ${error.message}`, 'error');
            return false;
        }
    }

    async validateServiceWorkers() {
        try {
            const registrations = await navigator.serviceWorker.getRegistrations();
            
            for (let registration of registrations) {
                if (!registration || registration.active === null) {
                    this.logger.log('Unregistering service worker: Invalid registration', 'warn');
                    await registration.unregister();
                    continue;
                }

                const subscription = await registration.pushManager.getSubscription();
                
                if (!subscription || subscription.unsubscribe === undefined) {
                    this.logger.log('Unregistering service worker: No valid subscription', 'warn');
                    await registration.unregister();
                    continue;
                }

                const isUserActive = await this.checkUserStatus(subscription.endpoint);
                
                if (!isUserActive) {
                    this.logger.log(`Unregistering service worker due to inactive user: ${subscription.endpoint}`, 'warn');
                    await registration.unregister();

                    if (this.registrationMode === 'auto') {
                        try {
                            await this.registerServiceWorker('pushBaseSW.js');
                            await this.requestNotificationPermission();
                        } catch (registrationError) {
                            this.logger.log(`Failed to re-register service worker: ${registrationError.message}`, 'error');
                        }
                    }
                } else {
                    this.logger.log(`Service worker is valid and user is active: ${subscription.endpoint}`, 'info');
                }
            }
        } catch (error) {
            this.logger.log(`Error validating service workers: ${error.message}`, 'error');
        }
    }

    async requestNotificationPermission() {
        try {
            const registration = await this.registerServiceWorker('pushBaseSW.js');
            const permission = await Notification.requestPermission();

            if (permission === 'granted') {
                const token = await getToken(this.messaging, {
                    vapidKey: '{{VAPID_PUBLIC_KEY}}',
                    serviceWorkerRegistration: registration
                });

                if (token) {
                    const existingRegistrations = await navigator.serviceWorker.getRegistrations();
                    for (let existingReg of existingRegistrations) {
                        const existingSubscription = await existingReg.pushManager.getSubscription();
                        if (existingSubscription) {
                            const isUserActive = await this.checkUserStatus(existingSubscription.endpoint);
                            if (isUserActive) {
                                this.logger.log('Existing active subscription found. Skipping new subscription.', 'info');
                                return token;
                            }
                        }
                    }

                    const subscription = await this.subscribe(registration);
                    await this.sendSubscriptionToServer(subscription);
                    return token;
                } else {
                    throw new Error('Failed to get registration token');
                }
            } else {
                throw new Error('Notification permission denied');
            }
        } catch (error) {
            this.logger.log(`Error requesting notification permission: ${error.message}`, 'error');
            throw error;
        }
    }

    onMessageReceived(callback) {
        onMessage(this.messaging, (payload) => {
            try {
                const now = Date.now();
                const notificationKey = JSON.stringify({
                    title: payload.notification?.title,
                    body: payload.notification?.body,
                    data: payload.data,
                    swId: this.firebaseConfig.appId
                });

                const recentNotifications = JSON.parse(
                    localStorage.getItem(this.NOTIFICATION_STORAGE_KEY) || '[]'
                );

                const filteredNotifications = recentNotifications.filter(
                    item => now - item.timestamp < 5 * 60 * 1000
                );

                const isDuplicate = filteredNotifications.some(
                    item => item.key === notificationKey
                );

                if (isDuplicate) {
                    this.logger.log(`Duplicate message suppressed: ${notificationKey}`, 'info');
                    return;
                }

                filteredNotifications.push({
                    key: notificationKey,
                    timestamp: now
                });

                localStorage.setItem(
                    this.NOTIFICATION_STORAGE_KEY,
                    JSON.stringify(filteredNotifications)
                );

                this.logger.log(`Message received: ${JSON.stringify(payload)}`, 'info');
                if (callback) callback(payload);
            } catch (error) {
                this.logger.log(`Error processing message: ${error.message}`, 'error');
            }
        });
    }

    async unsubscribe() {
        return super.unsubscribe();
    }
}

export default PushBaseClient;