import { GraphQLModule as _ } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { getConfig } from 'src/app.config';
import { ExecutionContext } from 'src/execution-context';
import { PrismaModule } from 'src/prisma';

export const GraphQLModule = _.forRootAsync({
  imports: [PrismaModule],
  inject: [PrismaClient],
  useFactory(prisma: PrismaClient) {
    const options = getConfig();
    return {
      autoSchemaFile: options.isProduction ? true : 'schema.graphql',
      sortSchema: true,
      debug: !options.isProduction,
      playground: !options.isProduction,
      path: options.endpoint,
      context({ req }) {
        return ExecutionContext.create(prisma, req);
      },
    };
  },
});
