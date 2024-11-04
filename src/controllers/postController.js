import logger from '../utils/logger.js';
import wordpressService from '../services/wordpressService.js';

export const postController = {
  createPost: async (req, res, next) => {
    try {
      const { title, content, status } = req.body;
      
      if (!title || !content) {
        return res.status(400).json({
          status: 'error',
          message: 'Title and content are required'
        });
      }

      logger.info(`Creating new post: ${title}`);

      const post = await wordpressService.createPost({
        title: { rendered: title },
        content: { rendered: content },
        status: status || 'draft'
      });

      res.status(201).json({
        status: 'success',
        message: 'Post created successfully',
        data: post
      });
    } catch (error) {
      next(error);
    }
  },

  getPosts: async (req, res, next) => {
    try {
      const { page = 1, limit = 10, status = 'publish' } = req.query;

      logger.info(`Fetching posts. Page: ${page}, Limit: ${limit}, Status: ${status}`);

      const { posts, total } = await wordpressService.getPosts(page, limit, status);

      res.status(200).json({
        status: 'success',
        message: 'Posts retrieved successfully',
        data: {
          posts,
          page: parseInt(page),
          limit: parseInt(limit),
          total: parseInt(total)
        }
      });
    } catch (error) {
      next(error);
    }
  },

  getPost: async (req, res, next) => {
    try {
      const { id } = req.params;

      logger.info(`Fetching post with ID: ${id}`);

      const post = await wordpressService.getPost(id);

      res.status(200).json({
        status: 'success',
        message: 'Post retrieved successfully',
        data: post
      });
    } catch (error) {
      next(error);
    }
  },

  updatePost: async (req, res, next) => {
    try {
      const { id } = req.params;
      const { title, content, status } = req.body;

      logger.info(`Updating post with ID: ${id}`);

      const post = await wordpressService.updatePost(id, {
        title: title ? { rendered: title } : undefined,
        content: content ? { rendered: content } : undefined,
        status
      });

      res.status(200).json({
        status: 'success',
        message: 'Post updated successfully',
        data: post
      });
    } catch (error) {
      next(error);
    }
  },

  deletePost: async (req, res, next) => {
    try {
      const { id } = req.params;

      logger.info(`Deleting post with ID: ${id}`);

      const result = await wordpressService.deletePost(id);

      res.status(200).json({
        status: 'success',
        message: 'Post deleted successfully',
        data: result
      });
    } catch (error) {
      next(error);
    }
  }
};