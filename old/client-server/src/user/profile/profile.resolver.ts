import { Args, Mutation, Resolver } from '@nestjs/graphql';
import { Authorization, HasTokenExpiredType } from '@socfony/auth';
import { Context, ID } from '@socfony/kernel';
import { PrismaClient } from '@socfony/prisma';
import { UpdateUserProfileInput } from './dto';
import { UserProfileEntity } from './entities';

@Resolver((of) => UserProfileEntity)
export class UserProfileResolver {
  constructor(
    private readonly prisma: PrismaClient,
    private readonly context: Context,
  ) {}

  /**
   * Update user profile data.
   * @param viewer HTTP endpoint authorization user
   * @param data User profile update input data.
   */
  @Mutation((returns) => UserProfileEntity, {
    description: 'Update user profile',
  })
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.AUTH,
  })
  updateUserProfile(
    @Args({
      name: 'data',
      type: () => UpdateUserProfileInput,
      description: 'User profile update input data',
    })
    data: UpdateUserProfileInput,
  ) {
    return this.prisma.userProfile.upsert({
      where: { userId: this.context.user.id },
      create: Object.assign({}, data, {
        id: ID.generator(32),
        userId: this.context.user.id,
      }),
      update: data,
    });
  }
}
