import { NestFactory } from '@nestjs/core';
import { registerAppConfig } from './app.config';
import { AppModule } from './app.module';

export async function createClientServerApp(configPath?: string) {
  registerAppConfig(configPath);
  const app = await NestFactory.create(AppModule);

  return app;
}
