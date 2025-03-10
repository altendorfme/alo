# PushBase 🚀📬

[![Forks](https://img.shields.io/github/forks/altendorfme/pushbase)](https://github.com/altendorfme/pushbase/network/members)
[![Stars](https://img.shields.io/github/stars/altendorfme/pushbase)](https://github.com/altendorfme/pushbase/stargazers)
[![Issues](https://img.shields.io/github/issues/altendorfme/pushbase)](https://github.comaltendorfme/pushbasea/issues)

![PushBase](https://github.com/altendorfme/pushbase/blob/main/pushbase.gif?raw=true)

PushBase is a tool for managing and registering Web Push notifications. It also provides features for campaign management, analytics, and user segmentation.

PushBase is the open source alternative to OneSignal, PushNews, SendPulse, PushAlert, and others.

## Installation with Docker 🐳

Install [Docker and Docker Compose](https://docs.docker.com/engine/install/)

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/altendorfme/pushbase/main/docker-compose.yml`

`curl -o ./.env https://raw.githubusercontent.com/altendorfme/pushbase/main/docker.env.example`

Edit environment:

`nano .env`

| Variable | Default | Description |
|----------|---------|-------------|
| TZ | UTC | Timezone for the application |
| WORKERS | 1 | Each worker is a push service, each worker sends 5-6 pushes per second. Increasing the number of workers will directly impact database usage. |
| RABBITMQ_DEFAULT_USER | Empty | Username for RabbitMQ message broker authentication |
| RABBITMQ_DEFAULT_PASS | Empty | Password for RabbitMQ message broker authentication |
| RABBITMQ_DEFAULT_VHOST | pushbase | Virtual host for RabbitMQ to isolate applications |
| MYSQL_DATABASE | pushbase | Name of the MySQL database for PushBase |
| MYSQL_USER | pushbase | Username for MySQL database authentication |
| MYSQL_PASSWORD | Empty | Password for MySQL database authentication |

Now just run `docker compose up -d`

## First-Time Setup 🔧
1. Access the installation at `https://site.xyz/install`
2. Complete the initial configuration
3. Log in to the admin panel at `https://site.xyz/login`

## Who is using it?
- [Manual do Usuário](https://manualdousuario.net)

## PushBase SDK 📱

You'll need the PushBase Client SDK on all your pages:

```javascript
import PushBaseClient from 'https://pushbase-server.xyz/clientSDK';
```

Just drop this in, and you're ready to roll! The SDK gets set up with different options depending on what you want to do with it.

### Automatic Subscription ⚡

This option will show the permission prompt after a short delay - no user clicks needed!

```javascript
const pushBaseConfig = {
    customSegments: {
        tag: 'tag-teste-auto',
        category: 'cat-teste-auto'
    },
    registrationMode: 'auto',  // ← Magic happens here!
    registrationDelay: 3000,   // Just 3 seconds wait
    enableLogging: true,
    onRegistrationSuccess: () => {
        alert('Push notification registration successful!');
    },
    onRegistrationError: (error) => {
        alert('Registration failed: ' + error.message);
    }
};
const pushBaseClient = new PushBaseClient(pushBaseConfig);
```

The secret sauce here is `registrationMode: 'auto'` - this tells PushBase to automatically ask for permission. You can control how long to wait with the `registrationDelay` setting.

### Manual Subscription 👆

Prefer to let users decide when to subscribe? This approach requires a click before asking for permission.

```javascript
const pushBaseConfig = {
    customSegments: {
        tag: 'tag-test-manual',
        category: 'cat-test-manual'
    },
    registrationMode: 'manual',  // ← User control!
    enableLogging: true
};
const pushBaseClient = new PushBaseClient(pushBaseConfig);

subscribeBtn.addEventListener('click', async () => {
    try {
        await pushBaseClient.subscribe();
        alert('Push notification subscription successful!');
        subscribeBtn.disabled = true;
    } catch (error) {
        alert('Subscription failed: ' + error.message);
    }
});
```

With `registrationMode: 'manual'`, PushBase waits patiently until you call `subscribe()`. Perfect for adding to a "Subscribe" button!

### Unsubscription 👋

Sometimes users want to say goodbye to notifications. Here's how to let them unsubscribe:

```javascript
const pushBaseConfig = {
    customSegments: {
        tag: 'tag-test-unsubscribe',
        category: 'cat-test-unsubscribe'
    },
    enableLogging: true
};

const pushBaseClient = new PushBaseClient(pushBaseConfig);

unsubscribeBtn.addEventListener('click', async () => {
    try {
        await pushBaseClient.unsubscribe();
        alert('Push notification unsubscription successful!');
        unsubscribeBtn.disabled = true;
    } catch (error) {
        alert('Unsubscription failed:' + error.message);
    }
});
```

Just hook up an "Unsubscribe" button to the `unsubscribe()` method, and you're good to go!

### 🤖 Service Worker

Your notifications need a Service Worker to function even when users aren't on your site:

```javascript
importScripts('https://pushbase-server.xyz/serviceWorker');
```

Just put this in your `pushBaseSW.js` file, and PushBase handles all the background notification magic for you! ✨

## API 📡

### Create Campaign

Creates a new campaign with `draft` status.

**URL**: `/api/campaign/create` (POST)

**Request Body**:
```json
{
  "name": "My Campaign",
  "push_title": "Check out our new feature!",
  "push_body": "We've just launched something amazing you'll love.",
  "push_icon": "https://example.com/icon.png",
  "push_url": "https://example.com/landing",
  "push_requireInteraction": true
}
```

**cURL Example**:
```bash
curl -X POST \
  https://pushbase-server.xyz/api/campaign/create \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "My Campaign",
    "push_title": "Check out our new feature!",
    "push_body": "We have just launched something amazing you will love.",
    "push_icon": "https://example.com/icon.png",
    "push_url": "https://example.com/landing"
  }'
```

**Success Response**:
```json
{
  "success": true,
  "message": "Campaign created successfully",
  "campaign": {
    "id": 123,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "My Campaign",
    "push_title": "Check out our new feature!",
    "push_body": "We have just launched something amazing you will love.",
    "push_icon": "https://example.com/icon.png",
    "push_url": "https://example.com/landing",
    "status": "draft",
    "created_at": "2025-03-05 13:58:45",
    "updated_at": "2025-03-05 13:58:45"
  }
}
```

**Error Response**:
```json
{
  "error": "Field 'push_body' is required"
}
```

## Contributing 🤝
Made with ❤️! If you have questions or suggestions, open an issue and we'll help!
