import { Parent, ResolveField, Resolver } from '@nestjs/graphql';
import { User } from '@prisma/client';
import { UserInterface } from './entities/user.interface';
import { UserProfileEntity } from './profile/entities/profile.entity';
import { UserProfileService } from './profile/profile.service';

@Resolver(() => UserInterface)
export class UserInterfaceResolver {
  constructor(private readonly userProfileService: UserProfileService) {}

  @ResolveField(() => UserProfileEntity)
  profile(@Parent() user: User) {
    return this.userProfileService.resolveProfile(user);
  }
}
