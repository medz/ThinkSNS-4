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
          moments: false;
        };
      }>,
      'password'
    > {
  /**
   * User ID
   */
  @Field((type) => ID, {
    description: 'User ID',
  })
  id: string;

  /**
   * User login name
   */
  @Field((type) => String, {
    nullable: true,
    description: 'User login name',
  })
  login: string;

  /**
   * User bound E-Mail address.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'User bound E-Mail address.',
  })
  email: string;

  /**
   * User bound Phone full number.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'User bound Phone full number.',
  })
  phone: string;
  
  /**
   * Has the user set a password.
   */
  @Field(() => Boolean, {
    nullable: true,
    description: 'Has the user set a password.',
  })
  isSetPassword: boolean;

  /**
   * User registered date at.
   */
  @Field((type) => GraphQLISODateTime, {
    description: 'User registered date at.',
  })
  createdAt: Date;

  /**
   * User profile.
   */
  @Field((type) => UserProfileEntity, {
    description: 'The user profile',
  })
  profile: UserProfile;
}
