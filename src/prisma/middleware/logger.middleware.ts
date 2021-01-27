import { Logger } from '@nestjs/common';
import { Prisma } from '@prisma/client';

/**
 * Create Prisma logger middleware helper.
 * @param logger Logger
 */
export function PrismaLoggerMiddleware(logger: Logger): Prisma.Middleware {
  return function (params, next) {
    logger.debug(
      `Query ${params.model}.${params.action}\nArgs: ${JSON.stringify(
        params.args,
        null,
        2,
      )}`,
      'Prisma',
    );

    return next(params);
  };
}
