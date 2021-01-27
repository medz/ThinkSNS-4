import { Args, Mutation, Resolver } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { HasTokenExpiredType } from 'src/authorization-token/enums';
import { Authorization } from 'src/authorization.decorator';
import { Context } from 'src/context';
import { UserProfileUpdateInput } from './dto/profile-update.input';
import { UserProfileEntity } from './entities/profile.entity';
import { UserProfileService } from './profile.service';

@Resolver(() => UserProfileEntity)
export class UserProfileResolver {
  constructor(
    private readonly profileService: UserProfileService,
    private readonly prismaClient: PrismaClient,
    private readonly context: Context,
  ) {}

  @Mutation(() => UserProfileEntity)
  @Authorization({ hasAuthorization: true, type: HasTokenExpiredType.AUTH })
  async updateViewerProfile(
    @Args({
      name: 'data',
      type: () => UserProfileUpdateInput,
      description: 'Viewer profile update data.',
    })
    data: UserProfileUpdateInput,
  ) {
    const profile = await this.profileService.resolveProfile(this.context.user);
    return await this.prismaClient.userProfile.update({
      where: { userId: profile.userId },
      data,
    });
  }
}
