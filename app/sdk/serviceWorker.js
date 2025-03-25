"use strict";

class ServiceWorkerLogger {
    log(message, level = 'info') {
        const timestamp = new Date().toISOString();
        const logLevels = {
            'info': console.log,
            'warn': console.warn,
            'error': console.error
        };

        const logMethod = logLevels[level] || console.log;
        logMethod(`[${timestamp}] [Alô ServiceWorker] [${level.toUpperCase()}] ${message}`);
    }
}

const logger = new ServiceWorkerLogger();

async function safeFetch(url, options) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response;
    } catch (error) {
        logger.log(`Fetch error: ${error.message}`, 'error');
        return null;
    }
}

async function handlePushEvent(event) {
    try {
        let data;
        try {
            data = event.data?.json();
        } catch (parseError) {
            logger.log(`Failed to parse push event data: ${parseError.message}`, 'error');
            return;
        }

        if (!data) {
            logger.log('No data in push event', 'error');
            return;
        }

        logger.log(`Received push event data: ${JSON.stringify(data)}`, 'info');

        const notification = {
            title: data.notification?.title || '',
            body: data.notification?.body || null,
            icon: data.notification?.icon || null,
            badge: data.notification?.badge || null,
            image: data.notification?.image || '',
            click_action: data.notification?.click_action || '/',
            uuid: data.notification?.uuid || Date.now().toString(),
            tag: data.notification?.tag || `alo-${Date.now()}`,
            requireInteraction: !!data.notification?.requireInteraction,
            actions: Array.isArray(data.notification?.actions)
                ? data.notification.actions.filter(action =>
                    action.action && action.title
                ).map(action => ({
                    action: action.action,
                    title: action.title,
                    url: action.url || null
                }))
                : []
        };

        const subscriber = {
            id: data.subscriber?.id || null,
            metadata: data.subscriber?.metadata || {}
        };

        const campaign = {
            id: data.campaign?.id || null,
            name: data.campaign?.name || 'Unknown Campaign',
            type: data.campaign?.type || 'default'
        };

        if (!notification.title) {
            logger.log('Invalid notification: Missing title', 'error');
            return;
        }

        const notificationOptions = {
            body: notification.body,
            icon: notification.icon,
            badge: notification.badge,
            image: notification.image,
            tag: notification.tag,
            requireInteraction: notification.requireInteraction,
            data: {
                clickAction: notification.click_action,
                taskId: notification.uuid,
                source: "{{FIREBASE_APPID}}",
                campaignId: campaign.id,
                campaignName: campaign.name,
                campaignType: campaign.type,
                subscriberId: subscriber.id,
                subscriberMetadata: subscriber.metadata
            }
        };

        if (notification.actions.length > 0) {
            notificationOptions.actions = notification.actions;
        }

        logger.log(`Prepared notification: ${JSON.stringify(notificationOptions)}`, 'info');

        const analyticsPayload = {
            subscriberId: subscriber.id,
            campaignId: campaign.id,
            campaignName: campaign.name,
            type: "delivered",
            timestamp: Date.now(),
            notificationDetails: {
                title: notification.title,
                tag: notification.tag,
                hasActions: notification.actions.length > 0
            }
        };

        try {
            await safeFetch(`{{APP_URL}}/api/subscriber/analytics`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Service-Worker-Version": "1.1.0"
                },
                body: JSON.stringify(analyticsPayload)
            });
        } catch (analyticsError) {
            logger.log(`Analytics tracking failed: ${analyticsError.message}`, 'warn');
        }

        try {
            return await self.registration.showNotification(
                notification.title,
                notificationOptions
            );
        } catch (notificationError) {
            logger.log(`Failed to show notification: ${notificationError.message}`, 'error');
        }

    } catch (error) {
        logger.log(`Unhandled error in push event processing: ${error.message}`, 'error');
        logger.log(`Error stack: ${error.stack}`, 'info');
    }
}

self.addEventListener("install", (event) => {
    logger.log('Service worker installing', 'info');
    event.waitUntil(self.skipWaiting());
});

self.addEventListener("activate", (event) => {
    logger.log('Service worker activating', 'info');
    event.waitUntil(self.clients.claim());
});

self.addEventListener("push", (event) => {
    logger.log('Push event received', 'info');
    event.waitUntil(handlePushEvent(event));
});

self.addEventListener("notificationclick", (event) => {
    const { notification, action } = event;
    let clickAction = notification.data.clickAction || '/';

    if (action && notification.actions) {
        const button = notification.actions.find((btn) => btn.action === action);
        if (button && button.url) {
            clickAction = button.url;
        }
    }

    logger.log(`Notification clicked: ${JSON.stringify(event)}`, 'info');

    if (notification.data.campaignId && notification.data.subscriberId) {
        const clickAnalytics = {
            subscriberId: notification.data.subscriberId,
            type: "clicked",
            campaignId: notification.data.campaignId
        };

        logger.log(`Sending click analytics: ${JSON.stringify(clickAnalytics)}`, 'info');

        safeFetch(`{{APP_URL}}/api/subscriber/analytics`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Service-Worker-Version": logger.version
            },
            body: JSON.stringify(clickAnalytics)
        });
    }

    notification.close();

    event.waitUntil(
        clients.matchAll({ type: "window" }).then((clientList) => {
            const focusedClient = clientList.find((client) => 
                client.url === clickAction && "focus" in client
            );

            if (focusedClient) {
                return focusedClient.focus();
            }

            if (clients.openWindow) {
                return clients.openWindow(clickAction);
            }
        })
    );
});

self.addEventListener('sync', (event) => {
    logger.log(`Background sync event: ${event.tag}`, 'info');
});