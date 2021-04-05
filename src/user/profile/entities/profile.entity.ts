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
  @Field(() => String, {
    description: 'User name.',
    nullable: true,
  })
  name: string;

  /**
   * User avatar storage path
   */
  @Field(() => String, {
    description: 'User avatar storage path.',
    nullable: true,
  })
  avatar: string;

  /**
   * User bio
   */
  @Field(() => String, {
    description: 'User bio.',
    nullable: true,
  })
  bio: string;

  /**
   * User location string
   */
  @Field(() => String, {
    description: 'User location string.',
    nullable: true,
  })
  location: string;

  /**
   * User profile updated at
   */
  @Field(() => GraphQLISODateTime, {
    description: 'User profile updated at.',
    nullable: true,
  })
  updatedAt: Date;
}
