import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createProduct,
  deleteProduct,
  getProduct,
  listProducts,
  updateProduct,
  type ProductFilters,
  type ProductPayload,
} from '../api/products'
import { listProductCategories } from '../api/productCategories'
import { queryKeys } from './queryKeys'

export function useProducts(filters: ProductFilters = {}) {
  return useQuery({
    queryKey: queryKeys.products(filters),
    queryFn: () => listProducts(filters),
  })
}

export function useProduct(id: string | undefined) {
  return useQuery({
    queryKey: queryKeys.product(id ?? ''),
    queryFn: () => getProduct(id as string, ['category']),
    enabled: Boolean(id),
  })
}

export function useProductCategories() {
  return useQuery({
    queryKey: queryKeys.productCategories(),
    queryFn: () => listProductCategories(),
  })
}

export function useSaveProduct(id?: string) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: ProductPayload) =>
      id ? updateProduct(id, payload) : createProduct(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
      // A product's category affects the products_count shown on categories.
      queryClient.invalidateQueries({ queryKey: queryKeys.productCategories() })
    },
  })
}

export function useDeleteProduct() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: string) => deleteProduct(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
      queryClient.invalidateQueries({ queryKey: queryKeys.productCategories() })
    },
  })
}
