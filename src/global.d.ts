import { ExecutionContext } from './execution-context';

declare module 'express' {
  export interface Request {
    context: ExecutionContext;
  }
}
