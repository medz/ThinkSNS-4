import { ArgsType, IntersectionType, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { CreateAuthorizationTokenArgs } from 'src/authorization-token/dto/create-authorization-token.args';
import { UserEntity } from '../entities/user.entity';
import { UserUpdatePasswordArgs } from './user-update-password.args';

@ArgsType()
export class ForgotPasswordArgs
  extends PickType(
    IntersectionType(
      CreateAuthorizationTokenArgs,
      IntersectionType(UserEntity, UserUpdatePasswordArgs),
    ),
    ['phone', 'password', 'security'] as const,
    ArgsType,
  )
  implements Pick<Prisma.UserUpdateInput, 'phone' | 'password'> {}
