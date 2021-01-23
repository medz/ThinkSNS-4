import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@socfony/prisma';

@InputType()
export class UpdateUserProfileInput
  implements Omit<Prisma.UserProfileUpdateInput, 'id' | 'updatedAt' | 'user'> {
  @Field((type) => String, {
    description: 'User name.',
    nullable: true,
    defaultValue: undefined,
  })
  name?: string;

  @Field((type) => String, {
    description: 'User avatar storage path.',
    nullable: true,
    defaultValue: undefined,
  })
  avatar?: string;

  @Field((type) => String, {
    description: 'User licartion string.',
    nullable: true,
    defaultValue: undefined,
  })
  location?: string;

  @Field((type) => String, {
    description: 'User bio.',
    nullable: true,
    defaultValue: undefined,
  })
  bio?: string;
}
