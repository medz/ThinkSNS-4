import { Context } from './runtime';

declare global {
    interface RuntimeContext extends Context {}
}
