import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

export const config = {
  port: process.env.PORT || 3000,
  nodeEnv: process.env.NODE_ENV || 'development',
  wordpress: {
    apiUrl: process.env.WP_API_URL || 'http://localhost/wp-json/wp/v2',
    username: process.env.WP_USERNAME,
    password: process.env.WP_PASSWORD
  },
  cors: {
    origin: process.env.CORS_ORIGIN || '*'
  },
  morgan: {
    format: process.env.NODE_ENV === 'production' ? 'combined' : 'dev'
  }
};