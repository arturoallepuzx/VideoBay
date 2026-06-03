export type TmdbPath = string | null;

export interface MovieCard {
  movie_id: string;
  title: string;
  poster_path: TmdbPath;
  release_year: number | null;
  tmdb_rating: number | null;
}

export interface MoviesPage {
  items: MovieCard[];
  page: number;
  total_pages: number;
  total: number;
}

export interface MovieGenre {
  tmdb_id: number | null;
  name: string;
}

export interface MovieCredit {
  person_uuid: string;
  person_tmdb_id: number | null;
  person_name: string | null;
  person_profile_path: TmdbPath;
  department: string;
  job: string | null;
  character_name: string | null;
  credit_order: number | null;
}

export interface MovieDetail {
  uuid: string;
  tmdb_id: number | null;
  imdb_id: string | null;
  title: string;
  original_title: string | null;
  overview: string | null;
  release_date: string | null;
  runtime_minutes: number | null;
  original_language: string | null;
  poster_path: TmdbPath;
  backdrop_path: TmdbPath;
  tmdb_rating: number | null;
  genres: MovieGenre[];
  credits: MovieCredit[];
  video_file_id: string | null;
  streamable: boolean;
}

export interface MovieSearchResult {
  tmdb_id: number | null;
  title: string;
  original_title: string | null;
  overview: string | null;
  release_date: string | null;
  poster_path: TmdbPath;
  backdrop_path: TmdbPath;
  tmdb_rating: number | null;
  original_language: string | null;
}

export interface MovieSearchPage {
  results: MovieSearchResult[];
  page: number;
  total_pages: number;
  total_results: number;
}

export interface SimilarMoviesPage {
  results: Omit<MovieSearchResult, 'original_title' | 'original_language'>[];
  page: number;
  total_pages: number;
  total_results: number;
}

export interface PersonDetail {
  uuid: string;
  tmdb_id: number | null;
  name: string;
  biography: string | null;
  profile_path: TmdbPath;
  birthday: string | null;
  deathday: string | null;
  place_of_birth: string | null;
}

export interface PersonSearchResult {
  tmdb_id: number | null;
  name: string;
  profile_path: TmdbPath;
  known_for_department: string | null;
}

export interface PersonSearchPage {
  results: PersonSearchResult[];
  page: number;
  total_pages: number;
  total_results: number;
}

export interface FilmographyEntry {
  tmdb_id: number | null;
  title: string;
  release_date: string | null;
  poster_path: TmdbPath;
  character_name: string | null;
  job: string | null;
  department: string | null;
}

export interface Filmography {
  cast: FilmographyEntry[];
  crew: FilmographyEntry[];
}

export type BarcodeResolveSource =
  | 'local_cache'
  | 'tmdb_candidates'
  | 'external_title_only'
  | 'unresolved';

export interface BarcodeCandidate {
  tmdb_id: number | null;
  title: string;
  release_date: string | null;
  poster_path: TmdbPath;
  overview: string | null;
}

export interface BarcodeResolveResult {
  barcode: string;
  source: BarcodeResolveSource;
  movie: { uuid: string; tmdb_id: number | null; title: string; poster_path: TmdbPath } | null;
  external_title: string | null;
  candidates: BarcodeCandidate[];
}
