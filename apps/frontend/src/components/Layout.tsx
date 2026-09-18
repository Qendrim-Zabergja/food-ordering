import { NavLink, Outlet } from 'react-router'
import { useAuth } from '../hooks/useAuth'
import { useCart } from '../hooks/useCart'
import { RouteName } from '../enums/RouteName'
import { PermissionSlug } from '../enums/PermissionSlug'

function navClass({ isActive }: { isActive: boolean }): string {
  return isActive ? 'header__link header__link--active' : 'header__link'
}

/** "Demo Customer" -> "DC", "Administrator" -> "A" */
function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('')
}

export function Layout() {
  const { user, isAuthenticated, logout, can } = useAuth()

  // Only asked for when signed in - the endpoint needs a token.
  const { data: cart } = useCart(isAuthenticated)

  return (
    <div className="app">
      <header className="header">
        <div className="header__inner">
          <NavLink to={RouteName.MENU} className="header__brand">
            🍕 Food Ordering
          </NavLink>

          {isAuthenticated ? (
            <>
              <nav className="header__nav">
                <NavLink to={RouteName.MENU} className={navClass}>
                  Menu
                </NavLink>

                <NavLink to={RouteName.CART} className={navClass}>
                  Cart
                  {cart && cart.items_count > 0 ? (
                    <span className="header__count">{cart.items_count}</span>
                  ) : null}
                </NavLink>

                <NavLink to={RouteName.ORDERS} className={navClass}>
                  Orders
                </NavLink>

                {/*
                  "Manage menu" rather than "Admin": orders are handled from the
                  Orders page now, so this section edits one thing - the menu the
                  customer browses. "Admin" only said who it was for, and the link
                  is already hidden from anyone without the permission.
                */}
                {can(PermissionSlug.MANAGE_PRODUCTS) ||
                can(PermissionSlug.MANAGE_PRODUCT_CATEGORIES) ? (
                  <NavLink to={RouteName.ADMIN_PRODUCTS} className={navClass}>
                    Manage menu
                  </NavLink>
                ) : null}
              </nav>

              {/*
                Who you are, kept out of the nav. It was reading as a fourth
                button beside Menu / Cart / Orders; an avatar and a name with the
                email beneath it says identity rather than navigation.
              */}
              <div className="account">
                <span className="account__avatar" aria-hidden="true">
                  {initials(user?.name ?? '')}
                </span>

                <span className="account__text">
                  <span className="account__name">{user?.name}</span>
                  <span className="account__email">{user?.email}</span>
                </span>

                <button type="button" className="btn btn--sm" onClick={() => void logout()}>
                  Sign out
                </button>
              </div>
            </>
          ) : (
            <nav className="header__nav">
              <NavLink to={RouteName.LOGIN} className={navClass}>
                Sign in
              </NavLink>
              <NavLink to={RouteName.REGISTER} className="btn btn--primary btn--sm">
                Create account
              </NavLink>
            </nav>
          )}
        </div>
      </header>

      <main className="app__main">
        <Outlet />
      </main>
    </div>
  )
}
