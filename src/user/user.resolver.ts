import { Args, Query, Resolver } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { USER_NOT_FOUND } from 'src/constants';
import { UserWhereUniqueInput } from './dto';
import { UserEntity } from './entities/user.entity';
import { UserUnion } from './entities/user.union';

/**
 * User entity resolver
 */
@Resolver(() => UserEntity)
export class UserResolver {
  /**
   * Create user entity resolver.
   * @param prismaClient Prisma client.
   */
  constructor(private readonly prismaClient: PrismaClient) {}

  /**
   * Query a user where unique
   * @param where Query a user where unique
   */
  @Query(() => UserUnion, {
    description: 'query where.',
  })
  user(
    @Args({
      name: 'where',
      type: () => UserWhereUniqueInput,
      description: 'query where',
    })
    where: UserWhereUniqueInput,
  ) {
    return this.prismaClient.user.findUnique({
      where,
      rejectOnNotFound: () => new Error(USER_NOT_FOUND),
    });
  }
}
