import { Args, Query, Resolver } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { USER_NOT_FOUND } from 'src/constants';
import { UserWhereUniqueInput } from './dto';
import { UserEntity } from './entities/user.entity';
import { UserUnion } from './entities/user.union';

@Resolver(() => UserEntity)
export class UserResolver {
  constructor(private readonly prismaClient: PrismaClient) {}

  @Query(() => UserUnion, {
    description: 'Query a user where unique.',
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
