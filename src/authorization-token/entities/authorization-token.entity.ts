import { Field, ID, ObjectType } from '@nestjs/graphql';
import { Prisma, User } from '@prisma/client';
import { ViewerEntity } from 'src/user/entities/viewer.entity';
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
  @Field((type) => ID, {
    description: 'Login user ID',
  })
  userId: string;

  /**
   * Logged User
   */
  @Field((type) => ViewerEntity, {
    description: 'Logged Viewer entity',
  })
  user: User;

  /**
   * User API endpoit authorization token.
   */
  @Field((type) => String, {
    description: 'User API endpoit authorization token.',
  })
  token: string;

  /**
   * Token expired date.
   */
  @Field((type) => Date, {
    description: 'Token expired date',
  })
  expiredAt: Date;

  /**
   * Token on refresh expired date.
   */
  @Field((type) => Date, {
    description: 'Token on refresh expired date',
  })
  refreshExpiredAt: Date;
}
