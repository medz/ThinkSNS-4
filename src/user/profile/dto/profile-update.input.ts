import { InputType, OmitType, PartialType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserProfileEntity } from '../entities/profile.entity';

@InputType()
export class UserProfileUpdateInput
  extends OmitType(
    PartialType(UserProfileEntity),
    ['updatedAt', 'userId'] as const,
    InputType,
  )
  implements Omit<Prisma.UserProfileUpdateInput, 'user'> {}
