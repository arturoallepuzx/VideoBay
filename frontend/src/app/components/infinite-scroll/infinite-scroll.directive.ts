import { Directive, ElementRef, OnDestroy, OnInit, inject, output } from '@angular/core';

/** Emite (reached) cuando el elemento entra en el viewport, para cargar la siguiente página. */
@Directive({ selector: '[vbInfiniteScroll]' })
export class InfiniteScrollDirective implements OnInit, OnDestroy {

  readonly reached = output<void>();

  private readonly host = inject(ElementRef<HTMLElement>);
  private observer?: IntersectionObserver;

  ngOnInit(): void {
    this.observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          this.reached.emit();
        }
      },
      { rootMargin: '400px' },
    );

    this.observer.observe(this.host.nativeElement);
  }

  ngOnDestroy(): void {
    this.observer?.disconnect();
  }
}
