import { Field, GraphQLISODateTime, ID, ObjectType } from '@nestjs/graphql';
import { Prisma, UserProfile } from '@prisma/client';
import { UserProfileEntity } from '../profile/entities/profile.entity';

/**
 * User entity.
 */
@ObjectType({
  description: 'User entity',
})
export class UserEntity
  implements
    Omit<
      Prisma.UserGetPayload<{
        include: {
          profile: true;
        };
      }>,
      'password'
    > {
  /**
   * User ID
   */
  @Field(() => ID, {
    description: 'User ID',
  })
  id: string;

  /**
   * User username
   */
  @Field(() => String, {
    nullable: true,
    description: 'User username',
  })
  username: string;

  /**
   * User bound Phone full number.
   */
  @Field(() => String, {
    nullable: true,
    description: 'User bound Phone full number.',
  })
  phone: string;

  /**
   * Has the user set a password.
   */
  @Field(() => Boolean, {
    nullable: false,
    description: 'Has the user set a password.',
  })
  isSetPassword: boolean;

  /**
   * User registered date at.
   */
  @Field(() => GraphQLISODateTime, {
    description: 'User registered date at.',
  })
  createdAt: Date;

  /**
   * User profile.
   */
  @Field(() => UserProfileEntity, {
    description: 'The user profile',
  })
  profile: UserProfile;
}
