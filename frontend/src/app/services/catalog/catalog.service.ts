import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../api/base-api.service';
import {
  BarcodeResolveResult,
  Filmography,
  MovieDetail,
  MoviesPage,
  MovieSearchPage,
  PersonDetail,
  PersonSearchPage,
  SimilarMoviesPage,
} from './catalog.models';

export interface MovieCatalogFilters {
  genre?: string;
  year_from?: number;
  year_to?: number;
  sort?: string;
  page?: number;
  per_page?: number;
}

@Injectable({ providedIn: 'root' })
export class CatalogService extends BaseApiService {

  listStreamable(filters: MovieCatalogFilters = {}): Observable<MoviesPage> {
    return this.get<MoviesPage>('/catalog/movies/streamable', { ...filters });
  }

  listPurchasable(filters: MovieCatalogFilters = {}): Observable<MoviesPage> {
    return this.get<MoviesPage>('/catalog/movies/purchasable', { ...filters });
  }

  searchMovies(query: string, page = 1): Observable<MovieSearchPage> {
    return this.get<MovieSearchPage>('/catalog/movies/search', { query, page });
  }

  getMovie(identifier: string): Observable<MovieDetail> {
    return this.get<MovieDetail>(`/catalog/movies/${identifier}`);
  }

  getSimilar(identifier: string): Observable<SimilarMoviesPage> {
    return this.get<SimilarMoviesPage>(`/catalog/movies/${identifier}/similar`);
  }

  searchPeople(query: string, page = 1): Observable<PersonSearchPage> {
    return this.get<PersonSearchPage>('/catalog/people/search', { query, page });
  }

  getPerson(identifier: string): Observable<PersonDetail> {
    return this.get<PersonDetail>(`/catalog/people/${identifier}`);
  }

  getFilmography(identifier: string): Observable<Filmography> {
    return this.get<Filmography>(`/catalog/people/${identifier}/filmography`);
  }

  resolveBarcode(barcode: string): Observable<BarcodeResolveResult> {
    return this.post<BarcodeResolveResult>('/catalog/barcode/resolve', { barcode });
  }
}
