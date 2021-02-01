import { Parent, ResolveField, Resolver } from '@nestjs/graphql';
import { User } from '@prisma/client';
import { UserInterface } from './entities/user.interface';
import { UserProfileEntity } from './profile/entities/profile.entity';
import { UserProfileService } from './profile/profile.service';

/**
 * GraphQL user interface resolver.
 */
@Resolver(() => UserInterface)
export class UserInterfaceResolver {
  /**
   * Create GraphQL user interface resolver
   * @param userProfileService User profile service.
   */
  constructor(private readonly userProfileService: UserProfileService) {}

  /**
   * Resolve user profile field.
   * @param user Parent context user.
   */
  @ResolveField(() => UserProfileEntity)
  profile(@Parent() user: User) {
    return this.userProfileService.resolveProfile(user);
  }
}
