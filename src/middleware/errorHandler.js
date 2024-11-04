import logger from '../utils/logger.js';

// Global error handler
export const errorHandler = (err, req, res, next) => {
  logger.error('Error:', err);
  
  const statusCode = err.status || 500;
  const message = err.message || 'Internal Server Error';
  
  res.status(statusCode).json({
    status: 'error',
    message,
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
};

// 404 handler
export const notFoundHandler = (req, res) => {
  res.status(404).json({
    status: 'error',
    message: 'Route not found'
  });
};