import {
  compare as comparePassword,
  hash as createPasswordHash,
  genSalt,
} from 'bcrypt';

export namespace PasswordHelper {
  export function compare(password: string, encrypted: string) {
    return comparePassword(password, encrypted);
  }

  export async function hash(password: string) {
    const salt = await genSalt(10, 'b');
    return await createPasswordHash(password, salt);
  }
}
