import { Field, GraphQLISODateTime, ID, ObjectType } from '@nestjs/graphql';
import { UserProfile } from '@socfony/prisma';

@ObjectType()
export class UserProfileEntity implements UserProfile {
  @Field((type) => ID, {
    description: 'User profile ID',
  })
  id: string;

  @Field((type) => ID, {
    description: 'Profile owner ID',
  })
  userId: string;

  @Field((type) => String, {
    description: 'User name.',
    nullable: true,
  })
  name: string;

  @Field((type) => String, {
    description: 'User avatar storage path.',
    nullable: true,
  })
  avatar: string;

  @Field((type) => String, {
    description: 'User bio.',
    nullable: true,
  })
  bio: string;

  @Field((type) => String, {
    description: 'User location string.',
    nullable: true,
  })
  location: string;

  @Field((type) => GraphQLISODateTime, {
    description: 'User profile updated at.',
    nullable: true,
  })
  updatedAt: Date;
}
