import request from 'supertest';
import app from '../src/app.js';

describe('Health Check Endpoint', () => {
  it('should return 200 and healthy status', async () => {
    const response = await request(app)
      .get('/api/health')
      .expect('Content-Type', /json/)
      .expect(200);

    expect(response.body).toEqual(
      expect.objectContaining({
        status: 'success',
        message: 'Server is healthy',
        timestamp: expect.any(String)
      })
    );
  });
});

describe('Error Handling', () => {
  it('should return 404 for non-existent routes', async () => {
    const response = await request(app)
      .get('/api/non-existent')
      .expect('Content-Type', /json/)
      .expect(404);

    expect(response.body).toEqual(
      expect.objectContaining({
        status: 'error'
      })
    );
  });
});