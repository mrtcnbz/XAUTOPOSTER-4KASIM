# Node.js Starter Application

Modern Node.js application with Express.js

## Features

- Express.js web framework
- ES Modules support
- Security middleware with Helmet
- CORS enabled
- Request logging with Morgan
- Environment configuration with dotenv
- Error handling middleware
- Jest testing setup
- Development mode with Nodemon

## Getting Started

1. Install dependencies:
   ```bash
   npm install
   ```

2. Start development server:
   ```bash
   npm run dev
   ```

3. Run tests:
   ```bash
   npm test
   ```

## API Endpoints

- GET `/api/health` - Health check endpoint

## Environment Variables

Create a `.env` file in the root directory:

```
PORT=3000
NODE_ENV=development
```