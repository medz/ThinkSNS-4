import { resolve } from 'path';
import { createClientServerApp } from './app.factory';
import { Logger } from '@nestjs/common';

const logger = new Logger('Development Server');

async function bootstrap() {
  // create application
  const app = await createClientServerApp(resolve(__dirname, '../../../.env'));
  await app.listen(process.env.APP_PORT);

  const listeningURL = await app.getUrl();
  logger.log(`Application HTTP listening ${listeningURL}`);
  logger.log(
    `Application WebSocket listening ${listeningURL.replace('http', 'ws')}`,
  );
}

bootstrap();
