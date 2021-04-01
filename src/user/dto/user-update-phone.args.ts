import { ArgsType, Field, IntersectionType, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { CreateAuthorizationTokenArgs } from 'src/authorization-token/dto/create-authorization-token.args';
import { UserEntity } from '../entities/user.entity';

/**
 * User update phone args.
 */
@ArgsType()
export class UserUpdatePhoneArgs
  extends PickType(
    IntersectionType(CreateAuthorizationTokenArgs, UserEntity),
    ['phone', 'type', 'security'] as const,
    ArgsType,
  )
  implements Pick<Prisma.UserUpdateInput, 'phone'> {
  /**
   * new phone security code.
   */
  @Field(() => String, {
    nullable: false,
    description: 'new phone security code',
  })
  newPhoneSecurity: string;
}
