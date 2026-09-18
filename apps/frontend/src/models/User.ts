import { Model, type ApiPayload } from './Model'
import type { PermissionSlug } from '../enums/PermissionSlug'

export class User extends Model {
  name = ''
  email = ''
  roles: string[] = []
  permissions: string[] = []

  /**
   * The only way the interface asks about authorization. Never check a role -
   * a role is a bundle of permissions today and a different bundle tomorrow.
   */
  can(permission: PermissionSlug | PermissionSlug[]): boolean {
    const required = Array.isArray(permission) ? permission : [permission]

    return required.every((slug) => this.permissions.includes(slug))
  }

  hydrate(data: ApiPayload): this {
    super.hydrate(data)

    this.roles = (data.roles as string[]) ?? []
    this.permissions = (data.permissions as string[]) ?? []

    return this
  }
}
