import { Field, ID, ObjectType } from '@nestjs/graphql';
import { Prisma, User } from '@prisma/client';
import { UserEntity } from 'src/user/entities/user.entity';
/**
 * HTTP endpoint authorization entity.
 */
@ObjectType({
  description: 'HTTP endpoint authorization entity.',
})
export class AuthorizationTokenEntity
  implements
    Prisma.AuthorizationTokenGetPayload<{
      include: {
        user: false;
      };
    }> {
  /**
   * Logged user id.
   */
  @Field(() => ID, {
    description: 'User ID',
  })
  userId: string;

  /**
   * Logged User
   */
  @Field(() => UserEntity, {
    description: 'Logged Viewer entity',
  })
  user: User;

  /**
   * User API endpoit authorization token.
   */
  @Field(() => String, {
    description: 'User API endpoit authorization token.',
  })
  token: string;

  /**
   * Token expired date.
   */
  @Field(() => Date, {
    description: 'Token expired date',
  })
  expiredAt: Date;

  /**
   * Token on refresh expired date.
   */
  @Field(() => Date, {
    description: 'Token on refresh expired date',
  })
  refreshExpiredAt: Date;
}
