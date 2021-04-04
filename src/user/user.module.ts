import { Module } from '@nestjs/common';
import { PrismaModule } from 'src/prisma';
import { SecurityModule } from 'src/security/security.module';
import { UserProfileModule } from './profile/profile.module';
import { UserEntityFieldResolver } from './resolvers/user-entity-field.resolver';
import { UserMutationResolver } from './resolvers/user-mutation.resolver';
import { UserQueryResolver } from './resolvers/user-query.resolver';
import { UserService } from './user.service';

/**
 * User module.
 */
@Module({
  imports: [PrismaModule, UserProfileModule, SecurityModule],
  providers: [
    UserService,
    UserEntityFieldResolver,
    UserMutationResolver,
    UserQueryResolver,
  ],
  exports: [UserService],
})
export class UserModule {}
