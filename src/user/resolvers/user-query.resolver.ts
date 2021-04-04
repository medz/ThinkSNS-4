import { Args, Query, Resolver } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { AuthorizationWith } from 'src/authorization.decorator';
import { USER_NOT_FOUND } from 'src/constants';
import { Context } from 'src/context.decorator';
import { ExecutionContext } from 'src/execution-context';
import { UserWhereUniqueInput } from '../dto/user-where-unique.input';
import { UserEntity } from '../entities/user.entity';

@Resolver(() => UserEntity)
export class UserQueryResolver {
  constructor(private readonly prismaClient: PrismaClient) {}

  /**
   * Query the HTTP endpoint Authorization user.
   * @param context Socfony execution context.
   */
  @Query(() => UserEntity, {
    description: 'Query the HTTP endpoint Authorization user.',
  })
  @AuthorizationWith()
  viewer(@Context() context: ExecutionContext) {
    return context.user;
  }

  /**
   * Query a user where unique
   * @param where Query a user where unique
   */
  @Query(() => UserEntity, {
    description: 'Find zero or one User that matches the filter.',
  })
  userFindUnique(
    @Args({
      name: 'where',
      type: () => UserWhereUniqueInput,
      description: 'User where unique input',
    })
    where: UserWhereUniqueInput,
  ) {
    return this.prismaClient.user.findUnique({
      where,
      rejectOnNotFound: () => new Error(USER_NOT_FOUND),
    });
  }
}
