import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import { AddCopyPayload, CopiesPage, CopyDetail, CopyFilters, PricingRules, SaleProposalPayload, UpdateCopyPayload, UpdatePricingPayload } from './inventory.models';

@Injectable({ providedIn: 'root' })
export class InventoryService extends BaseApiService {

  listCopies(filters: CopyFilters = {}): Observable<CopiesPage> {
    return this.get<CopiesPage>('/inventory/copies', { ...filters });
  }

  getCopy(copyId: string): Observable<CopyDetail> {
    return this.get<CopyDetail>(`/inventory/copies/${copyId}`);
  }

  proposeSale(payload: SaleProposalPayload): Observable<unknown> {
    return this.post('/inventory/proposals', payload);
  }

  addCopy(payload: AddCopyPayload): Observable<CopyDetail> {
    return this.post<CopyDetail>('/inventory/copies', payload);
  }

  updateCopy(copyId: string, payload: UpdateCopyPayload): Observable<CopyDetail> {
    return this.put<CopyDetail>(`/inventory/copies/${copyId}`, payload);
  }

  getPricing(): Observable<PricingRules> {
    return this.get<PricingRules>('/inventory/pricing');
  }

  updatePricing(payload: UpdatePricingPayload): Observable<PricingRules> {
    return this.put<PricingRules>('/inventory/pricing', payload);
  }
}
