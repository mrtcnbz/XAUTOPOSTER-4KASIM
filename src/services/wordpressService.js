import fetch from 'node-fetch';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';

class WordPressService {
  constructor() {
    this.baseUrl = config.wordpress.apiUrl;
    this.auth = Buffer.from(`${config.wordpress.username}:${config.wordpress.password}`).toString('base64');
  }

  async getPosts(page = 1, limit = 10, status = 'publish') {
    try {
      const response = await fetch(
        `${this.baseUrl}/posts?page=${page}&per_page=${limit}&status=${status}`,
        {
          headers: {
            'Authorization': `Basic ${this.auth}`,
            'Content-Type': 'application/json'
          }
        }
      );

      if (!response.ok) {
        throw new Error(`WordPress API error: ${response.statusText}`);
      }

      const posts = await response.json();
      const total = response.headers.get('X-WP-Total');

      return { posts, total };
    } catch (error) {
      logger.error('Error fetching posts:', error);
      throw error;
    }
  }

  async getPost(id) {
    try {
      const response = await fetch(`${this.baseUrl}/posts/${id}`, {
        headers: {
          'Authorization': `Basic ${this.auth}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`WordPress API error: ${response.statusText}`);
      }

      return response.json();
    } catch (error) {
      logger.error(`Error fetching post ${id}:`, error);
      throw error;
    }
  }

  async createPost(data) {
    try {
      const response = await fetch(`${this.baseUrl}/posts`, {
        method: 'POST',
        headers: {
          'Authorization': `Basic ${this.auth}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });

      if (!response.ok) {
        throw new Error(`WordPress API error: ${response.statusText}`);
      }

      return response.json();
    } catch (error) {
      logger.error('Error creating post:', error);
      throw error;
    }
  }

  async updatePost(id, data) {
    try {
      const response = await fetch(`${this.baseUrl}/posts/${id}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Basic ${this.auth}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });

      if (!response.ok) {
        throw new Error(`WordPress API error: ${response.statusText}`);
      }

      return response.json();
    } catch (error) {
      logger.error(`Error updating post ${id}:`, error);
      throw error;
    }
  }

  async deletePost(id) {
    try {
      const response = await fetch(`${this.baseUrl}/posts/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Basic ${this.auth}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`WordPress API error: ${response.statusText}`);
      }

      return response.json();
    } catch (error) {
      logger.error(`Error deleting post ${id}:`, error);
      throw error;
    }
  }
}

export default new WordPressService();