import { Global, Module } from '@nestjs/common';
import { Context } from './context';
import { PrismaModule } from './prisma';
import { GraphQLModule } from '@nestjs/graphql';
import { SecurityModule } from './security/security.module';
import { getConfig } from './app.config';
import { AuthorizationTokenModule } from './authorization-token/authorization-token.module';
import { UserModule } from './user/user.module';

@Global()
@Module({
  imports: [
    GraphQLModule.forRootAsync({
      inject: [Context],
      useFactory(context: Context) {
        const options = getConfig();
        return {
          autoSchemaFile: true,
          debug: !options.isProduction,
          playground: !options.isProduction,
          path: options.endpoint,
          context({ req }) {
            return context.create(req);
          },
        };
      },
    }),
    PrismaModule,
    AuthorizationTokenModule,
    UserModule,
    SecurityModule,
  ],
  providers: [Context],
  exports: [Context],
})
export class AppModule {}
