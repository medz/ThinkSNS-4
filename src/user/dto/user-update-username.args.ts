import { ArgsType, IntersectionType, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { CreateAuthorizationTokenArgs } from 'src/authorization-token/dto/create-authorization-token.args';
import { UserEntity } from '../entities/user.entity';

/**
 * User update username args.
 */
@ArgsType()
export class UserUpdateUsernameArgs
  extends PickType(
    IntersectionType(CreateAuthorizationTokenArgs, UserEntity),
    ['username', 'type', 'security'] as const,
    ArgsType,
  )
  implements Pick<Prisma.UserUpdateInput, 'username'> {}
