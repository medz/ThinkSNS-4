import { Args, Context, Mutation, Resolver } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { AuthorizationWith } from 'src/authorization.decorator';
import { ExecutionContext } from 'src/execution-context';
import { UserProfileUpdateInput } from './dto/profile-update.input';
import { UserProfileEntity } from './entities/profile.entity';
import { UserProfileService } from './profile.service';

@Resolver(() => UserProfileEntity)
export class UserProfileResolver {
  constructor(
    private readonly profileService: UserProfileService,
    private readonly prismaClient: PrismaClient,
  ) {}

  @Mutation(() => UserProfileEntity)
  @AuthorizationWith()
  async updateViewerProfile(
    @Context() context: ExecutionContext,
    @Args({
      name: 'data',
      type: () => UserProfileUpdateInput,
      description: 'Viewer profile update data.',
    })
    data: UserProfileUpdateInput,
  ) {
    const profile = await this.profileService.resolveProfile(context.user);
    return await this.prismaClient.userProfile.update({
      where: { userId: profile.userId },
      data,
    });
  }
}
