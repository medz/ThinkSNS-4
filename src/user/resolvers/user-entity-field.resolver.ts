import { Parent, ResolveField, Resolver } from '@nestjs/graphql';
import { User } from '@prisma/client';
import { UserEntity } from '../entities/user.entity';
import { UserProfileEntity } from '../profile/entities/profile.entity';
import { UserProfileService } from '../profile/profile.service';

/**
 * UserEntity field resolver.
 */
@Resolver(() => UserEntity)
export class UserEntityFieldResolver {
  /**
   * User entity field resolver constructor.
   * @param userProfileService User profile service.
   */
  constructor(private readonly userProfileService: UserProfileService) {}

  /**
   * Resolver viewer phone field.
   * @param user Parent context user.
   */
  @ResolveField(() => String)
  phone(@Parent() user: User) {
    const { phone } = user;
    if (phone) {
      return phone.replace(/(.*)\d{4}(\d{4})/, '$1****$2');
    }

    return phone;
  }

  /**
   * resolve user `isSetPassword` field.
   * @param user Parent context user.
   * @returns boolean
   */
  @ResolveField(() => Boolean)
  isSetPassword(@Parent() user: User) {
    return !!user.password;
  }

  /**
   * Resolve user profile field.
   * @param user Parent context user.
   */
  @ResolveField(() => UserProfileEntity)
  profile(@Parent() user: User) {
    return this.userProfileService.resolveProfile(user);
  }
}
