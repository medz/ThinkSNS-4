import {
  Args,
  Mutation,
  Parent,
  ResolveField,
  Resolver,
} from '@nestjs/graphql';
import { Authorization, HasTokenExpiredType } from '@socfony/auth';
import { Context } from '@socfony/kernel';
import { User } from '@socfony/prisma';
import { UserService } from '@socfony/user';
import { UserInterface, ViewerEntity } from './entities';
import { UserProfileEntity, UserProfileService } from './profile';

@Resolver(() => UserInterface)
export class UserInterfaceResolver {
  constructor(
    private readonly userProfileService: UserProfileService,
    private readonly context: Context,
    private readonly userService: UserService,
  ) {}

  @ResolveField(() => UserProfileEntity)
  profile(@Parent() parent: User) {
    return this.userProfileService.resolveProfile(parent);
  }

  @Mutation(() => ViewerEntity, {
    description: "Update user login."
  })
  @Authorization({ hasAuthorization: true, type: HasTokenExpiredType.AUTH })
  updateUserLoginName(
    @Args({ name: 'login', type: () => String }) login: string,
  ) {
    const { user: viewer } = this.context;
    return this.userService.updateLogin(viewer, login);
  }

  @Mutation(() => ViewerEntity, {
    description: "updfate user password",
  })
  updateUserPassword() {}
}
