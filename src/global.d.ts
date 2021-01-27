import { Context } from './context';

declare module 'express' {
  export interface Request {
    context: Context;
  }
}
