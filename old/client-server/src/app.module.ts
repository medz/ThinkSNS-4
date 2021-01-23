import { Module } from '@nestjs/common';
import { GraphQLModule } from '@nestjs/graphql';
import { Context, KernelModule } from '@socfony/kernel';
import { UserModule } from './user';
import { getAppConfig } from './app.config';
import { AuthModule } from './auth';
import { SecurityCodeModule } from './security-code';

@Module({
  imports: [
    GraphQLModule.forRootAsync({
      imports: [KernelModule],
      inject: [Context],
      useFactory(context: Context) {
        const { endpoint } = getAppConfig();
        return {
          autoSchemaFile: true,
          installSubscriptionHandlers: false,
          debug: process.env.NODE_ENV === 'development',
          playground: process.env.NODE_ENV === 'development',
          path: endpoint,
          async context({ req }) {
            const value = await context.create(req);

            return value;
          },
        };
      },
    }),
    AuthModule,
    SecurityCodeModule,
    UserModule,
  ],
})
export class AppModule {}
