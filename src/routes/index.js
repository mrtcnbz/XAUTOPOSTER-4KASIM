import express from 'express';
import { healthCheck } from '../controllers/healthController.js';
import { postController } from '../controllers/postController.js';
import { notFoundHandler } from '../middleware/errorHandler.js';

export const router = express.Router();

// Health check endpoint
router.get('/health', healthCheck);

// WordPress endpoints
router.post('/posts', postController.createPost);
router.get('/posts', postController.getPosts);
router.get('/posts/:id', postController.getPost);
router.put('/posts/:id', postController.updatePost);
router.delete('/posts/:id', postController.deletePost);

// Catch 404 errors
router.use('*', notFoundHandler);