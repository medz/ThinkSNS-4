import { InputType, OmitType, PartialType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserProfileEntity } from '../entities/profile.entity';

/**
 * User profile update input
 */
@InputType({
  description: 'User profile update input',
})
export class UserProfileUpdateInput
  extends OmitType(
    PartialType(UserProfileEntity),
    ['updatedAt', 'userId'] as const,
    InputType,
  )
  implements Omit<Prisma.UserProfileUpdateInput, 'user'> {}
