import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { IonIcon } from '@ionic/angular/standalone';
import { CatalogService } from '../../../services/catalog/catalog.service';
import { FilmographyEntry, MovieCard, PersonDetail } from '../../../services/catalog/catalog.models';
import { MovieCardComponent } from '../../../components/movie-card/movie-card.component';
import { EmptyStateComponent } from '../../../components/empty-state/empty-state.component';
import { TmdbImagePipe } from '../../../pipes/tmdb-image.pipe';
import { WishlistService } from '../../../services/wishlist/wishlist.service';
import { ToastService } from '../../../services/ui/toast.service';

@Component({
  selector: 'app-person',
  imports: [IonIcon, MovieCardComponent, EmptyStateComponent, TmdbImagePipe],
  templateUrl: 'person.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PersonPage {

  protected readonly person = signal<PersonDetail | null>(null);
  protected readonly notFound = signal(false);
  protected readonly castCredits = signal<FilmographyEntry[]>([]);
  protected readonly crewCredits = signal<FilmographyEntry[]>([]);

  protected readonly directorCards = computed<MovieCard[]>(() =>
    this.crewCredits()
      .filter((credit) => credit.job === 'Director')
      .map((credit) => this.toCard(credit)),
  );

  private readonly catalog = inject(CatalogService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly location = inject(Location);
  private readonly wishlist = inject(WishlistService);
  private readonly toast = inject(ToastService);

  constructor() {
    this.route.paramMap.subscribe((params) => {
      const id = params.get('id');
      if (id) {
        this.load(id);
      }
    });
  }

  protected openMovie(id: string | number | null): void {
    if (id !== null && id !== '') {
      this.router.navigate(['/movie', id]);
    }
  }

  protected goBack(): void {
    this.location.back();
  }

  protected saveWish(card: MovieCard): void {
    this.wishlist.saveToWishlistByTmdb(card.movie_id).subscribe({
      next: () => this.toast.show('Añadido a la wishlist'),
      error: () => this.toast.show('No se pudo guardar'),
    });
  }

  protected saveLater(card: MovieCard): void {
    this.wishlist.saveToWatchLaterByTmdb(card.movie_id).subscribe({
      next: () => this.toast.show('Guardado para ver más tarde'),
      error: () => this.toast.show('No se pudo guardar'),
    });
  }

  protected creditYear(date: string | null): number | null {
    return date ? Number(date.slice(0, 4)) : null;
  }

  private load(id: string): void {
    this.notFound.set(false);

    this.catalog.getPerson(id).subscribe({
      next: (person) => this.person.set(person),
      error: () => {
        this.person.set(null);
        this.notFound.set(true);
      },
    });

    this.catalog.getFilmography(id).subscribe({
      next: (filmography) => {
        this.castCredits.set(filmography.cast);
        this.crewCredits.set(filmography.crew);
      },
      error: () => {
        this.castCredits.set([]);
        this.crewCredits.set([]);
      },
    });
  }

  private toCard(entry: FilmographyEntry): MovieCard {
    return {
      movie_id: String(entry.tmdb_id ?? ''),
      title: entry.title,
      poster_path: entry.poster_path,
      release_year: entry.release_date ? Number(entry.release_date.slice(0, 4)) : null,
      tmdb_rating: null,
    };
  }
}
