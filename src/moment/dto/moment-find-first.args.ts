import { ArgsType, OmitType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { MomentFindManyArgs } from './moment-find-many.args';

/**
 * Moment find first args.
 */
@ArgsType()
export class MomentFindFirstArgs
  extends OmitType(MomentFindManyArgs, ['skip', 'take'] as const, ArgsType)
  implements
    Omit<
      Prisma.MomentFindFirstArgs,
      'select' | 'include' | 'rejectOnNotFound'
    > {}
