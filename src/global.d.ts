import { ExecutionContext } from './execution-context';

/**
 * declare 'express' module.
 */
declare module 'express' {
  /**
   * merged Express request.
   */
  export interface Request {
    /**
     * Socfony execution context.
     */
    context: ExecutionContext;
  }
}
