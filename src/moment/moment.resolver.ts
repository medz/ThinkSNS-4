import { Args, Query, ResolveField, Resolver } from '@nestjs/graphql';
import { Moment, PrismaClient } from '@prisma/client';
import { MOMENT_NOT_FOUND, USER_NOT_FOUND } from 'src/constants';
import { UserEntity } from 'src/user/entities/user.entity';
import { MomentFindFirstArgs } from './dto/moment-find-first.args';
import { MomentFindManyArgs } from './dto/moment-find-many.args';
import { MomentWhereUniqueInput } from './dto/moment-where-unique.input';
import { MomentEntity } from './entities/moment.entity';

/**
 * Moment entity resolver.
 */
@Resolver(() => MomentEntity)
export class MomentResolver {
  constructor(private readonly prismaClient: PrismaClient) {}

  /**
   * Find zero or one Moment that matches the filter
   * @param where Find unique where
   */
  @Query(() => MomentEntity, {
    description: 'Find zero or one Moment that matches the filter',
  })
  momentFindUnique(
    @Args({
      name: 'where',
      type: () => MomentWhereUniqueInput,
      description: 'Find unique where',
    })
    where: MomentWhereUniqueInput,
  ) {
    return this.prismaClient.moment.findUnique({
      where,
      rejectOnNotFound: () => new Error(MOMENT_NOT_FOUND),
    });
  }

  /**
   * Find zero or more Moments that matches the filter.
   * @param args Find moments args
   */
  @Query(() => [MomentEntity], {
    description: 'Find zero or more Moments that matches the filter',
    nullable: 'items',
  })
  momentFindMany(
    @Args({ type: () => MomentFindManyArgs }) args: MomentFindManyArgs,
  ) {
    return this.prismaClient.moment.findMany(args);
  }

  /**
   * Find the first Moment that matches the filter.
   * @param args Find moment args
   */
  @Query(() => MomentEntity, {
    nullable: true,
    description: 'Find the first Moment that matches the filter.',
  })
  momentFindFirst(
    @Args({ type: () => MomentFindFirstArgs }) args: MomentFindFirstArgs,
  ) {
    return this.prismaClient.moment.findFirst(
      Object.assign({}, args, {
        rejectOnNotFound: false,
      }),
    );
  }

  /**
   * Resolve owner field.
   * @param parent Parent moment object
   */
  @ResolveField(() => UserEntity)
  owner(parent: Moment) {
    return this.prismaClient.user.findUnique({
      where: { id: parent.ownerId },
      rejectOnNotFound: () => new Error(USER_NOT_FOUND),
    });
  }
}
