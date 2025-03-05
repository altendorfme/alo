# PushBase 🚀📬

[![Forks](https://img.shields.io/github/forks/altendorfme/pushbase)](https://github.com/altendorfme/pushbase/network/members)
[![Stars](https://img.shields.io/github/stars/altendorfme/pushbase)](https://github.com/altendorfme/pushbase/stargazers)
[![Issues](https://img.shields.io/github/issues/altendorfme/pushbase)](https://github.comaltendorfme/pushbasea/issues)

PushBase is a tool for managing and registering Web Push notifications. It also provides features for campaign management, analytics, and user segmentation.

PushBase is the open source alternative to OneSignal, PushNews, SendPulse, PushAlert, and others.

## Installation with Docker 🐳

Install [Docker and Docker Compose](https://docs.docker.com/engine/install/)

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/altendorfme/pushbase/main/docker-compose.yml`

`curl -o ./.env https://raw.githubusercontent.com/altendorfme/pushbase/main/docker.env.example`

Edit environment:

`nano .env`

Now just run `docker compose up -d`

## First-Time Setup 🔧
1. Access the installation at `https://site.xyz/install`
2. Complete the initial configuration
3. Log in to the admin panel at `https://site.xyz/login`

## Who is using it?
- [Manual do Usuário](https://manualdousuario.net)

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
  https://your-pushbase-instance.com/api/campaign/create \
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
