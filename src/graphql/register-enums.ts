import { registerEnumType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';

/**
 * Register sort order enum.
 */
registerEnumType(Prisma.SortOrder, {
  name: 'sortOrder',
  description: 'Sort order',
});

/**
 * Reguster query mode enum
 */
registerEnumType(Prisma.QueryMode, {
  name: 'QueryMode',
  description: 'query mode',
});
