import { useMutation, useQueryClient } from '@tanstack/react-query'
import {
  createProductCategory,
  deleteProductCategory,
  updateProductCategory,
  type ProductCategoryPayload,
} from '../api/productCategories'
import { queryKeys } from './queryKeys'

function useInvalidateCategories() {
  const queryClient = useQueryClient()

  return () => {
    queryClient.invalidateQueries({ queryKey: queryKeys.productCategories() })
    // A category's name shows on every product card, and deleting one cascades
    // to its products on the server.
    queryClient.invalidateQueries({ queryKey: ['products'] })
  }
}

export function useSaveProductCategory(id?: string) {
  const invalidate = useInvalidateCategories()

  return useMutation({
    mutationFn: (payload: ProductCategoryPayload) =>
      id ? updateProductCategory(id, payload) : createProductCategory(payload),
    onSuccess: invalidate,
  })
}

export function useDeleteProductCategory() {
  const invalidate = useInvalidateCategories()

  return useMutation({
    mutationFn: (id: string) => deleteProductCategory(id),
    onSuccess: invalidate,
  })
}
