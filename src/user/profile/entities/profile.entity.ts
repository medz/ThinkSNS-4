import { Field, GraphQLISODateTime, ID, ObjectType } from '@nestjs/graphql';

/**
 * User profile entity.
 */
@ObjectType({
  description: 'User profile entity',
})
export class UserProfileEntity implements UserProfileEntity {
  /**
   * Profile owner ID
   */
  @Field(() => ID, {
    description: 'Profile owner ID',
  })
  userId: string;

  /**
   * User name
   */
  @Field((type) => String, {
    description: 'User name.',
    nullable: true,
  })
  name: string;

  /**
   * User avatar storage path
   */
  @Field((type) => String, {
    description: 'User avatar storage path.',
    nullable: true,
  })
  avatar: string;

  /**
   * User bio
   */
  @Field((type) => String, {
    description: 'User bio.',
    nullable: true,
  })
  bio: string;

  /**
   * User location string
   */
  @Field((type) => String, {
    description: 'User location string.',
    nullable: true,
  })
  location: string;

  /**
   * User profile updated at
   */
  @Field((type) => GraphQLISODateTime, {
    description: 'User profile updated at.',
    nullable: true,
  })
  updatedAt: Date;
}
