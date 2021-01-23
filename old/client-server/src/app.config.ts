import * as dotenv from 'dotenv';

export function registerAppConfig(path?: string) {
  dotenv.config({ path });
}

export function getAppConfig() {
  return {
    endpoint: process.env.CLIENT_SERVER_ENDPOINT || '/',
  };
}
