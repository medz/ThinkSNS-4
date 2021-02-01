import { Injectable } from '@nestjs/common';
import { Prisma, PrismaClient, User, UserProfile } from '@prisma/client';

/**
 * User profile service.
 */
@Injectable()
export class UserProfileService {
  /**
   * Create the user profile service.
   * @param prisma Prisma client.
   */
  constructor(private readonly prisma: PrismaClient) {}

  /**
   * Resolve user profile.
   * @param user need resolve profile user.
   */
  async resolveProfile(
    user:
      | User
      | Prisma.UserGetPayload<{
          include: {
            profile: true;
          };
        }>,
  ): Promise<UserProfile> {
    let { profile } = user as Prisma.UserGetPayload<{
      include: {
        profile: true;
      };
    }>;
    if (profile) {
      return profile;
    }
    profile = await this.prisma.userProfile.findUnique({
      where: { userId: user.id },
      rejectOnNotFound: false,
    });
    if (profile) {
      return profile;
    }

    return await this.prisma.userProfile.create({
      data: { userId: user.id },
    });
  }
}
