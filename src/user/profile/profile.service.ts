import { Injectable } from '@nestjs/common';
import { Prisma, PrismaClient, User, UserProfile } from '@prisma/client';

@Injectable()
export class UserProfileService {
  constructor(private readonly prisma: PrismaClient) {}

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
