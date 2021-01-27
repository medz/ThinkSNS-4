import { Global, Module } from '@nestjs/common';
import { ExecutionContext } from './execution-context';
import { PrismaModule } from './prisma';
import { GraphQLModule } from '@nestjs/graphql';
import { SecurityModule } from './security/security.module';
import { getConfig } from './app.config';
import { AuthorizationTokenModule } from './authorization-token/authorization-token.module';
import { UserModule } from './user/user.module';
import { APP_GUARD } from '@nestjs/core';
import { AuthorizationGuard } from './authorization.guard';
import { PrismaClient } from '@prisma/client';

@Global()
@Module({
  imports: [
    GraphQLModule.forRootAsync({
      imports: [PrismaModule],
      inject: [PrismaClient],
      useFactory(prisma: PrismaClient) {
        const options = getConfig();
        return {
          autoSchemaFile: true,
          debug: !options.isProduction,
          playground: !options.isProduction,
          path: options.endpoint,
          context({ req }) {
            return ExecutionContext.create(prisma, req);
          },
        };
      },
    }),
    PrismaModule,
    AuthorizationTokenModule,
    UserModule,
    SecurityModule,
  ],
  providers: [
    {
      provide: APP_GUARD,
      useClass: AuthorizationGuard,
    },
  ],
})
export class AppModule {}
