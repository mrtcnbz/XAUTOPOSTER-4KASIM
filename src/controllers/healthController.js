import { config } from '../config/index.js';

export const healthCheck = (req, res) => {
  res.status(200).json({
    status: 'success',
    message: 'Server is healthy',
    environment: config.nodeEnv,
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
};